<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    applications: Object,
    jobs: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const jobId = ref(props.filters.job_id || '');
const selectedApplications = ref([]);

const applyFilters = () => {
    router.get(route('employer.applications'), {
        search: search.value,
        status: status.value,
        job_id: jobId.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    status.value = '';
    jobId.value = '';
    applyFilters();
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'reviewed': 'bg-blue-100 text-blue-800',
        'shortlisted': 'bg-purple-100 text-purple-800',
        'interviewed': 'bg-indigo-100 text-indigo-800',
        'hired': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const updateApplicationStatus = (application, newStatus) => {
    if (confirm(`Are you sure you want to ${newStatus} this application?`)) {
        router.patch(route('applications.status.update', application.id), {
            status: newStatus
        });
    }
};

const bulkUpdateStatus = (newStatus) => {
    if (selectedApplications.value.length === 0) {
        alert('Please select applications first');
        return;
    }

    if (confirm(`Are you sure you want to ${newStatus} ${selectedApplications.value.length} application(s)?`)) {
        router.post(route('applications.bulk'), {
            application_ids: selectedApplications.value,
            action: newStatus,
        }, {
            onSuccess: () => {
                selectedApplications.value = [];
            }
        });
    }
};

const toggleApplicationSelection = (applicationId) => {
    const index = selectedApplications.value.indexOf(applicationId);
    if (index > -1) {
        selectedApplications.value.splice(index, 1);
    } else {
        selectedApplications.value.push(applicationId);
    }
};

const selectAllApplications = () => {
    if (selectedApplications.value.length === props.applications.data.length) {
        selectedApplications.value = [];
    } else {
        selectedApplications.value = props.applications.data.map(app => app.id);
    }
};

const downloadResume = (application) => {
    window.open(route('applications.resume.download', application.id), '_blank');
};

const scheduleInterview = (application) => {
    const interviewDate = prompt('Enter interview date and time (YYYY-MM-DD HH:MM):');
    if (interviewDate) {
        router.post(route('applications.interview.schedule', application.id), {
            interview_datetime: interviewDate,
            notes: 'Interview scheduled'
        });
    }
};
</script>

<template>
    <Head title="Application Management" />

    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Application Management
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Filters -->
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Applications</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input id="search" type="text" v-model="search" 
                                   placeholder="Candidate name or email..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" v-model="status"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="shortlisted">Shortlisted</option>
                                <option value="interviewed">Interviewed</option>
                                <option value="hired">Hired</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="job" class="block text-sm font-medium text-gray-700 mb-1">Job</label>
                            <select id="job" v-model="jobId"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Jobs</option>
                                <option v-for="job in jobs" :key="job.id" :value="job.id">
                                    {{ job.title }}
                                </option>
                            </select>
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

                <!-- Bulk Actions -->
                <div v-if="selectedApplications.length > 0" class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-blue-800">
                            {{ selectedApplications.length }} application(s) selected
                        </span>
                        <div class="flex gap-2">
                            <button @click="bulkUpdateStatus('reviewed')" 
                                    class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">
                                Mark Reviewed
                            </button>
                            <button @click="bulkUpdateStatus('shortlisted')" 
                                    class="text-sm bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded">
                                Shortlist
                            </button>
                            <button @click="bulkUpdateStatus('rejected')" 
                                    class="text-sm bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">
                                Reject
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Applications List -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" 
                                               :checked="selectedApplications.length === applications.data.length && applications.data.length > 0"
                                               @change="selectAllApplications"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Candidate
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Job
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Applied Date
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="application in applications.data" :key="application.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" 
                                               :checked="selectedApplications.includes(application.id)"
                                               @change="toggleApplicationSelection(application.id)"
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ application.graduate?.user?.name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ application.graduate?.user?.email }}
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    {{ application.graduate?.course?.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <Link :href="route('jobs.show', application.job.id)" 
                                                  class="hover:text-indigo-600">
                                                {{ application.job.title }}
                                            </Link>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['inline-flex px-2 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(application.status)]">
                                            {{ application.status?.toUpperCase() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(application.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <Link :href="route('applications.show', application.id)" 
                                                  class="text-indigo-600 hover:text-indigo-900">
                                                View
                                            </Link>
                                            
                                            <button v-if="application.resume_path" 
                                                    @click="downloadResume(application)"
                                                    class="text-green-600 hover:text-green-900">
                                                Resume
                                            </button>
                                            
                                            <!-- Status Actions -->
                                            <button v-if="application.status === 'pending'" 
                                                    @click="updateApplicationStatus(application, 'reviewed')"
                                                    class="text-blue-600 hover:text-blue-900">
                                                Review
                                            </button>
                                            
                                            <button v-if="['pending', 'reviewed'].includes(application.status)" 
                                                    @click="updateApplicationStatus(application, 'shortlisted')"
                                                    class="text-purple-600 hover:text-purple-900">
                                                Shortlist
                                            </button>
                                            
                                            <button v-if="application.status === 'shortlisted'" 
                                                    @click="scheduleInterview(application)"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                Interview
                                            </button>
                                            
                                            <button v-if="['shortlisted', 'interviewed'].includes(application.status)" 
                                                    @click="updateApplicationStatus(application, 'hired')"
                                                    class="text-green-600 hover:text-green-900">
                                                Hire
                                            </button>
                                            
                                            <button v-if="!['hired', 'rejected'].includes(application.status)" 
                                                    @click="updateApplicationStatus(application, 'rejected')"
                                                    class="text-red-600 hover:text-red-900">
                                                Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="applications.links" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <Link v-if="applications.prev_page_url" :href="applications.prev_page_url" 
                                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </Link>
                                <Link v-if="applications.next_page_url" :href="applications.next_page_url" 
                                      class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </Link>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Showing {{ applications.from }} to {{ applications.to }} of {{ applications.total }} results
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <Link v-for="link in applications.links" :key="link.label" 
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
                <div v-if="applications.data.length === 0" class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6 text-center">
                        <div class="text-gray-500 text-lg mb-4">No applications found</div>
                        <p class="text-gray-400 mb-4">
                            {{ Object.values(filters).some(f => f) ? 'Try adjusting your search filters.' : 'No applications have been received yet.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>