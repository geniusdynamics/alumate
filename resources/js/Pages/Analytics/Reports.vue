<template>
    <AppLayout title="Analytics Reports">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Analytics Reports
                </h2>
                <button
                    @click="showCreateModal = true"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                >
                    Create Report
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Reports List -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Report Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Schedule
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Last Execution
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="report in reports" :key="report.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ report.name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ report.description }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                {{ reportTypes[report.type] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div v-if="report.is_scheduled" class="flex items-center">
                                                <ClockIcon class="w-4 h-4 text-green-500 mr-1" />
                                                {{ report.schedule_frequency }}
                                            </div>
                                            <span v-else class="text-gray-500">Manual</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div v-if="report.last_execution">
                                                <div class="flex items-center">
                                                    <span :class="getStatusColor(report.last_execution.status)" class="w-2 h-2 rounded-full mr-2"></span>
                                                    {{ report.last_execution.status }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ report.last_execution.completed_at }}
                                                </div>
                                            </div>
                                            <span v-else class="text-gray-500">Never run</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <button
                                                    @click="previewReport(report)"
                                                    class="text-indigo-600 hover:text-indigo-900"
                                                >
                                                    Preview
                                                </button>
                                                <button
                                                    @click="executeReport(report)"
                                                    :disabled="executingReports.includes(report.id)"
                                                    class="text-green-600 hover:text-green-900 disabled:opacity-50"
                                                >
                                                    {{ executingReports.includes(report.id) ? 'Running...' : 'Run' }}
                                                </button>
                                                <button
                                                    @click="editReport(report)"
                                                    class="text-blue-600 hover:text-blue-900"
                                                >
                                                    Edit
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Report Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Report</h3>
                
                <form @submit.prevent="createReport">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Report Name</label>
                            <input
                                v-model="newReport.name"
                                type="text"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="newReport.description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Report Type</label>
                            <select
                                v-model="newReport.type"
                                @change="updateAvailableColumns"
                                required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option value="">Select a type</option>
                                <option v-for="(label, value) in reportTypes" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <div v-if="availableColumns.length > 0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Columns to Include</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="(label, value) in availableColumns" :key="value" class="flex items-center">
                                    <input
                                        v-model="newReport.columns"
                                        :value="value"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">{{ label }}</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="newReport.is_scheduled"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-700">Schedule this report</span>
                            </label>
                        </div>

                        <div v-if="newReport.is_scheduled">
                            <label class="block text-sm font-medium text-gray-700">Schedule Frequency</label>
                            <select
                                v-model="newReport.schedule_frequency"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="newReport.is_public"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                />
                                <span class="ml-2 text-sm text-gray-700">Make this report public</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="creating"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-md text-sm font-medium disabled:opacity-50"
                        >
                            {{ creating ? 'Creating...' : 'Create Report' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Preview Modal -->
        <Modal :show="showPreviewModal" @close="showPreviewModal = false" max-width="6xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Report Preview</h3>
                
                <div v-if="previewLoading" class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>

                <div v-else-if="previewData">
                    <div class="mb-4 text-sm text-gray-600">
                        Showing {{ previewData.preview_info?.showing || 0 }} of {{ previewData.preview_info?.total || 0 }} records
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th v-for="column in Object.keys(previewData.data[0] || {})" :key="column" 
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        {{ column.replace('_', ' ') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(row, index) in previewData.data" :key="index">
                                    <td v-for="(value, column) in row" :key="column" 
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCellValue(value) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="showPreviewModal = false"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 py-2 px-4 rounded-md text-sm font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Modal from '@/Components/Modal.vue'
import { ClockIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    reports: Array,
    reportTypes: Object,
})

const showCreateModal = ref(false)
const showPreviewModal = ref(false)
const creating = ref(false)
const previewLoading = ref(false)
const previewData = ref(null)
const executingReports = ref([])
const availableColumns = ref([])

const newReport = reactive({
    name: '',
    description: '',
    type: '',
    filters: {},
    columns: [],
    chart_config: null,
    is_scheduled: false,
    schedule_frequency: 'daily',
    schedule_config: {},
    is_public: false,
})

const columnOptions = {
    employment: {
        graduate_name: 'Graduate Name',
        course_name: 'Course',
        graduation_date: 'Graduation Date',
        employment_status: 'Employment Status',
        company_name: 'Company',
        job_title: 'Job Title',
        salary_range: 'Salary Range',
        employment_date: 'Employment Date',
    },
    course_performance: {
        course_name: 'Course Name',
        total_graduates: 'Total Graduates',
        employed_count: 'Employed Count',
        employment_rate: 'Employment Rate',
        average_salary: 'Average Salary',
        top_employers: 'Top Employers',
        skills_taught: 'Skills Taught',
    },
    job_market: {
        job_title: 'Job Title',
        company_name: 'Company',
        location: 'Location',
        salary_range: 'Salary Range',
        required_skills: 'Required Skills',
        application_count: 'Applications',
        posted_date: 'Posted Date',
        status: 'Status',
    },
}

const updateAvailableColumns = () => {
    availableColumns.value = columnOptions[newReport.type] || {}
    newReport.columns = []
}

const createReport = async () => {
    creating.value = true
    
    try {
        await router.post(route('analytics.reports.create'), newReport, {
            onSuccess: () => {
                showCreateModal.value = false
                resetNewReport()
            }
        })
    } finally {
        creating.value = false
    }
}

const resetNewReport = () => {
    Object.assign(newReport, {
        name: '',
        description: '',
        type: '',
        filters: {},
        columns: [],
        chart_config: null,
        is_scheduled: false,
        schedule_frequency: 'daily',
        schedule_config: {},
        is_public: false,
    })
    availableColumns.value = []
}

const previewReport = async (report) => {
    showPreviewModal.value = true
    previewLoading.value = true
    previewData.value = null
    
    try {
        const response = await fetch(route('analytics.reports.preview', report.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
        })
        
        const result = await response.json()
        
        if (result.success) {
            previewData.value = result.preview
        } else {
            alert('Failed to generate preview: ' + result.message)
        }
    } catch (error) {
        alert('Error generating preview: ' + error.message)
    } finally {
        previewLoading.value = false
    }
}

const executeReport = async (report) => {
    executingReports.value.push(report.id)
    
    try {
        const response = await fetch(route('analytics.reports.execute', report.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ format: 'csv' })
        })
        
        const result = await response.json()
        
        if (result.success) {
            alert('Report execution started. You will be notified when it completes.')
        } else {
            alert('Failed to execute report: ' + result.message)
        }
    } catch (error) {
        alert('Error executing report: ' + error.message)
    } finally {
        executingReports.value = executingReports.value.filter(id => id !== report.id)
    }
}

const editReport = (report) => {
    // TODO: Implement edit functionality
    alert('Edit functionality coming soon')
}

const getStatusColor = (status) => {
    const colors = {
        completed: 'bg-green-500',
        failed: 'bg-red-500',
        processing: 'bg-yellow-500',
        pending: 'bg-gray-500',
    }
    return colors[status] || colors.pending
}

const formatCellValue = (value) => {
    if (value === null || value === undefined) {
        return '-'
    }
    
    if (Array.isArray(value)) {
        return value.join(', ')
    }
    
    if (typeof value === 'object') {
        return JSON.stringify(value)
    }
    
    return String(value)
}
</script>