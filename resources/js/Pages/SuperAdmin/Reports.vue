<template>
    <AdminLayout>
        <Head title="System Reports" />

        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-white">System Reports</h1>
                    <p class="mt-1 text-sm text-gray-300">Generate and export comprehensive system reports</p>
                </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('super-admin.dashboard')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <ArrowLeftIcon class="-ml-1 mr-2 h-5 w-5" />
                            Back to Dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Report Controls -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Report Configuration</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="reportType" class="block text-sm font-medium text-gray-700">Report Type</label>
                            <select
                                id="reportType"
                                v-model="selectedReportType"
                                @change="updateReport"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option value="overview">System Overview</option>
                                <option value="institutions">Institution Performance</option>
                                <option value="employment">Employment Report</option>
                                <option value="jobs">Job Market Report</option>
                            </select>
                        </div>
                        <div>
                            <label for="timeframe" class="block text-sm font-medium text-gray-700">Timeframe</label>
                            <select
                                id="timeframe"
                                v-model="selectedTimeframe"
                                @change="updateReport"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option value="7">Last 7 days</option>
                                <option value="30">Last 30 days</option>
                                <option value="90">Last 90 days</option>
                                <option value="365">Last year</option>
                            </select>
                        </div>
                        <div>
                            <label for="format" class="block text-sm font-medium text-gray-700">Export Format</label>
                            <select
                                id="format"
                                v-model="selectedFormat"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            >
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="exportReport"
                                :disabled="isExporting"
                                class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
                            >
                                <DocumentArrowDownIcon class="-ml-1 mr-2 h-5 w-5" />
                                {{ isExporting ? 'Exporting...' : 'Export Report' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Content -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">{{ getReportTitle() }}</h2>
                    <p class="text-sm text-gray-500">{{ getReportDescription() }}</p>
                </div>
                <div class="p-6">
                    <!-- Overview Report -->
                    <div v-if="selectedReportType === 'overview'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ reports.overview?.total_users || 0 }}</div>
                                <div class="text-sm text-blue-600">Total Users</div>
                                <div class="text-xs text-gray-500">+{{ reports.overview?.new_users || 0 }} new</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ reports.overview?.total_jobs || 0 }}</div>
                                <div class="text-sm text-green-600">Total Jobs</div>
                                <div class="text-xs text-gray-500">+{{ reports.overview?.new_jobs || 0 }} new</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">{{ reports.overview?.total_applications || 0 }}</div>
                                <div class="text-sm text-purple-600">Total Applications</div>
                                <div class="text-xs text-gray-500">+{{ reports.overview?.new_applications || 0 }} new</div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-600">{{ reports.overview?.employment_rate || 0 }}%</div>
                                <div class="text-sm text-yellow-600">Employment Rate</div>
                                <div class="text-xs text-gray-500">Overall system</div>
                            </div>
                        </div>
                    </div>

                    <!-- Institution Report -->
                    <div v-if="selectedReportType === 'institutions'" class="space-y-6">
                        <div v-for="institution in reports.institutions" :key="institution.institution" class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ institution.institution }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div class="text-center">
                                    <div class="text-xl font-bold text-gray-900">{{ institution.report.total_graduates }}</div>
                                    <div class="text-sm text-gray-500">Total Graduates</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-green-600">{{ institution.report.employed_graduates }}</div>
                                    <div class="text-sm text-gray-500">Employed</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-blue-600">{{ institution.report.new_graduates }}</div>
                                    <div class="text-sm text-gray-500">New Graduates</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-bold text-purple-600">{{ institution.report.job_applications }}</div>
                                    <div class="text-sm text-gray-500">Applications</div>
                                </div>
                            </div>
                            
                            <!-- Course Performance -->
                            <div v-if="institution.report.course_performance?.length">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Course Performance</h4>
                                <div class="space-y-2">
                                    <div v-for="course in institution.report.course_performance" :key="course.course_name" class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">{{ course.course_name }}</span>
                                        <div class="flex items-center space-x-4">
                                            <span class="text-gray-900">{{ course.total_graduates }} graduates</span>
                                            <span :class="getEmploymentRateColor(course.employment_rate)">
                                                {{ Math.round(course.employment_rate) }}% employed
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employment Report -->
                    <div v-if="selectedReportType === 'employment'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Employment by Status</h3>
                                <div class="space-y-3">
                                    <div v-for="status in reports.employment?.employment_by_status" :key="status.employment_status" class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-3 h-3 rounded-full mr-3" :class="getEmploymentStatusColor(status.employment_status)"></div>
                                            <span class="text-sm font-medium text-gray-900 capitalize">{{ status.employment_status.replace('_', ' ') }}</span>
                                        </div>
                                        <span class="text-sm text-gray-500">{{ status.count }} graduates</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Top Employers</h3>
                                <div class="space-y-2">
                                    <div v-for="employer in reports.employment?.top_employers" :key="employer.current_company" class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">{{ employer.current_company }}</span>
                                        <span class="text-sm font-medium text-gray-900">{{ employer.count }} graduates</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Employment Changes</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Graduate</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Updated</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="change in reports.employment?.recent_employment_changes" :key="change.name">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ change.name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ change.course }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="getEmploymentStatusBadgeClass(change.status)">
                                                    {{ change.status.replace('_', ' ') }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ change.company || '-' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(change.updated_at) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Job Market Report -->
                    <div v-if="selectedReportType === 'jobs'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Job Categories</h3>
                                <div class="space-y-2">
                                    <div v-for="category in reports.jobs?.top_job_categories" :key="category.job_type" class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">{{ category.job_type || 'Other' }}</span>
                                        <span class="text-sm font-medium text-gray-900">{{ category.count }} jobs</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Application Success Rate</h3>
                                <div class="text-center">
                                    <div class="text-3xl font-bold text-green-600">{{ reports.jobs?.application_success_rate || 0 }}%</div>
                                    <div class="text-sm text-gray-500">Applications resulting in hire</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Top Performing Employers</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jobs</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filled</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applications</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="employer in reports.jobs?.employer_performance" :key="employer.company_name">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ employer.company_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ employer.total_jobs }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ employer.active_jobs }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">{{ employer.filled_jobs }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ employer.total_applications }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Components/AdminLayout.vue';
import {
    ArrowLeftIcon,
    DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';

const props = defineProps({
    reports: Object,
    reportType: String,
    timeframe: String,
});

const selectedReportType = ref(props.reportType);
const selectedTimeframe = ref(props.timeframe);
const selectedFormat = ref('excel');
const isExporting = ref(false);

const getReportTitle = () => {
    const titles = {
        'overview': 'System Overview Report',
        'institutions': 'Institution Performance Report',
        'employment': 'Employment Analysis Report',
        'jobs': 'Job Market Report',
    };
    return titles[selectedReportType.value] || 'System Report';
};

const getReportDescription = () => {
    const descriptions = {
        'overview': 'Comprehensive overview of system-wide metrics and performance',
        'institutions': 'Detailed analysis of institution performance and graduate outcomes',
        'employment': 'Employment trends and graduate career progression analysis',
        'jobs': 'Job market analysis and employer performance metrics',
    };
    return descriptions[selectedReportType.value] || 'System report data';
};

const getEmploymentRateColor = (rate) => {
    if (rate >= 80) return 'text-green-600';
    if (rate >= 60) return 'text-yellow-600';
    return 'text-red-600';
};

const getEmploymentStatusColor = (status) => {
    const colors = {
        'employed': 'bg-green-500',
        'self_employed': 'bg-blue-500',
        'unemployed': 'bg-red-500',
        'seeking_employment': 'bg-yellow-500',
        'further_study': 'bg-purple-500',
    };
    return colors[status] || 'bg-gray-500';
};

const getEmploymentStatusBadgeClass = (status) => {
    const classes = {
        'employed': 'bg-green-100 text-green-800',
        'self_employed': 'bg-blue-100 text-blue-800',
        'unemployed': 'bg-red-100 text-red-800',
        'seeking_employment': 'bg-yellow-100 text-yellow-800',
        'further_study': 'bg-purple-100 text-purple-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    return format(new Date(dateString), 'MMM dd, yyyy');
};

const updateReport = () => {
    router.get(route('super-admin.reports'), {
        type: selectedReportType.value,
        timeframe: selectedTimeframe.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

const exportReport = async () => {
    isExporting.value = true;
    
    try {
        const response = await fetch(route('super-admin.reports.export'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                type: selectedReportType.value,
                timeframe: selectedTimeframe.value,
                format: selectedFormat.value,
            }),
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `${selectedReportType.value}_report.${selectedFormat.value}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
        }
    } catch (error) {
        console.error('Export failed:', error);
    } finally {
        isExporting.value = false;
    }
};
</script>