<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    importHistory: Object,
    previewRows: Array,
    headers: Array,
    totalRows: Number,
    courses: Array,
});

const form = useForm({
    import_history_id: props.importHistory.id,
    resolve_conflicts: {},
});

const showAllRows = ref(false);
const displayedRows = computed(() => {
    return showAllRows.value ? props.previewRows : props.previewRows.slice(0, 5);
});

const submit = () => {
    form.post(route('graduates.import.store'));
};

const formatValue = (value) => {
    if (value === null || value === undefined) return '';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    return String(value);
};

const getHeaderDisplayName = (header) => {
    const displayNames = {
        'name': 'Name',
        'email': 'Email',
        'phone': 'Phone',
        'address': 'Address',
        'graduation_year': 'Graduation Year',
        'course_name': 'Course Name',
        'student_id': 'Student ID',
        'gpa': 'GPA',
        'academic_standing': 'Academic Standing',
        'employment_status': 'Employment Status',
        'current_job_title': 'Job Title',
        'current_company': 'Company',
        'current_salary': 'Salary',
        'employment_start_date': 'Employment Start Date',
        'skills': 'Skills',
        'certifications': 'Certifications',
        'allow_employer_contact': 'Allow Employer Contact',
        'job_search_active': 'Job Search Active',
    };
    return displayNames[header] || header.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const isRequiredField = (header) => {
    return ['name', 'email', 'graduation_year', 'course_name', 'employment_status'].includes(header);
};
</script>

<template>
    <Head title="Import Preview" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Import Preview
                </h2>
                <Link :href="route('graduates.import.create')" 
                      class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-medium py-2 px-4 rounded-md">
                    Back to Import
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- Import Summary -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Import Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ totalRows }}</div>
                                <div class="text-sm text-blue-600">Total Rows</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ headers.length }}</div>
                                <div class="text-sm text-green-600">Columns Detected</div>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-600">{{ previewRows.length }}</div>
                                <div class="text-sm text-yellow-600">Preview Rows</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">{{ importHistory.filename }}</div>
                                <div class="text-sm text-purple-600">File Name</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column Mapping -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Column Mapping</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="header in headers" :key="header" 
                                 :class="[
                                     'p-3 rounded-lg border',
                                     isRequiredField(header) ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'
                                 ]">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-gray-900">{{ getHeaderDisplayName(header) }}</span>
                                    <span v-if="isRequiredField(header)" 
                                          class="inline-flex px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                                        Required
                                    </span>
                                </div>
                                <div class="text-sm text-gray-600 mt-1">{{ header }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Preview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Data Preview</h3>
                            <button @click="showAllRows = !showAllRows" 
                                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                {{ showAllRows ? 'Show Less' : `Show All ${previewRows.length} Rows` }}
                            </button>
                        </div>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Row
                                        </th>
                                        <th v-for="header in headers" :key="header" 
                                            class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            {{ getHeaderDisplayName(header) }}
                                            <span v-if="isRequiredField(header)" class="text-red-500">*</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(row, index) in displayedRows" :key="index" class="hover:bg-gray-50">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ index + 2 }}
                                        </td>
                                        <td v-for="header in headers" :key="header" 
                                            class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            <div class="max-w-xs truncate" :title="formatValue(row[header])">
                                                {{ formatValue(row[header]) }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Validation Warnings -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Before You Proceed</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Review the data preview to ensure all information looks correct</li>
                                    <li>Make sure course names match exactly with available courses</li>
                                    <li>Duplicate emails will be flagged and skipped during import</li>
                                    <li>Invalid data will be reported after processing</li>
                                    <li>This action cannot be undone (but can be rolled back within 24 hours)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between">
                    <Link :href="route('graduates.import.create')" 
                          class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel Import
                    </Link>
                    
                    <form @submit.prevent="submit">
                        <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center px-6 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Processing Import...' : `Proceed with Import (${totalRows} rows)` }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>