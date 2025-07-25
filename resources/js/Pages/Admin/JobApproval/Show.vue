<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    job: Object,
    employer_jobs: Array,
});

const approveForm = useForm({
    notes: '',
});

const rejectForm = useForm({
    reason: '',
});

const formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString() : 'N/A';
};

const formatSalary = (job) => {
    if (!job.salary_min && !job.salary_max) return 'Negotiable';
    
    const min = job.salary_min ? Number(job.salary_min).toLocaleString() : '';
    const max = job.salary_max ? Number(job.salary_max).toLocaleString() : '';
    
    if (min && max) {
        return `${min} - ${max} (${job.salary_type})`;
    } else if (min) {
        return `From ${min} (${job.salary_type})`;
    } else if (max) {
        return `Up to ${max} (${job.salary_type})`;
    }
    
    return 'Negotiable';
};

const getJobTypeText = (type) => {
    const texts = {
        'full_time': 'Full Time',
        'part_time': 'Part Time',
        'contract': 'Contract',
        'internship': 'Internship',
        'temporary': 'Temporary',
    };
    return texts[type] || type;
};

const getWorkArrangementText = (arrangement) => {
    const texts = {
        'on_site': 'On Site',
        'remote': 'Remote',
        'hybrid': 'Hybrid',
    };
    return texts[arrangement] || arrangement;
};

const getExperienceLevelText = (level) => {
    const texts = {
        'entry': 'Entry Level',
        'junior': 'Junior',
        'mid': 'Mid Level',
        'senior': 'Senior',
        'executive': 'Executive',
    };
    return texts[level] || level;
};

const getVerificationStatusColor = (status) => {
    const colors = {
        'verified': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'rejected': 'bg-red-100 text-red-800',
        'suspended': 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const approve = () => {
    approveForm.post(route('admin.job-approval.approve', props.job.id));
};

const reject = () => {
    rejectForm.post(route('admin.job-approval.reject', props.job.id));
};
</script>

<template>
    <Head :title="`Review Job - ${job.title}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Review Job Posting
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ job.title }} • {{ job.employer.company_name }}
                    </p>
                </div>
                <Link
                    :href="route('admin.job-approval.index')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                >
                    Back to Queue
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Job Details -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Job Details</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Job Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getJobTypeText(job.job_type) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Work Arrangement</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getWorkArrangementText(job.work_arrangement) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Experience Level</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getExperienceLevelText(job.experience_level) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Minimum Experience</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.min_experience_years }} years</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Salary Range</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatSalary(job) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Target Course</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.course?.name || 'N/A' }}</dd>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Description</h4>
                                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ job.description }}</div>
                            </div>

                            <!-- Required Skills -->
                            <div v-if="job.required_skills && job.required_skills.length > 0" class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Required Skills</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="skill in job.required_skills"
                                        :key="skill"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                                    >
                                        {{ skill }}
                                    </span>
                                </div>
                            </div>

                            <!-- Preferred Qualifications -->
                            <div v-if="job.preferred_qualifications && job.preferred_qualifications.length > 0" class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Preferred Qualifications</h4>
                                <ul class="list-disc list-inside text-sm text-gray-900 space-y-1">
                                    <li v-for="qualification in job.preferred_qualifications" :key="qualification">
                                        {{ qualification }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Benefits -->
                            <div v-if="job.benefits && job.benefits.length > 0" class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Benefits & Perks</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        v-for="benefit in job.benefits"
                                        :key="benefit"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                    >
                                        {{ benefit }}
                                    </span>
                                </div>
                            </div>

                            <!-- Company Culture -->
                            <div v-if="job.company_culture" class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Company Culture</h4>
                                <div class="text-sm text-gray-900 whitespace-pre-wrap">{{ job.company_culture }}</div>
                            </div>

                            <!-- Contact Information -->
                            <div v-if="job.contact_person || job.contact_email || job.contact_phone" class="mb-6">
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Contact Information</h4>
                                <div class="text-sm text-gray-900 space-y-1">
                                    <div v-if="job.contact_person">Contact Person: {{ job.contact_person }}</div>
                                    <div v-if="job.contact_email">Email: {{ job.contact_email }}</div>
                                    <div v-if="job.contact_phone">Phone: {{ job.contact_phone }}</div>
                                </div>
                            </div>

                            <!-- Important Dates -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500">Posted Date</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(job.created_at) }}</dd>
                                </div>
                                <div v-if="job.application_deadline">
                                    <dt class="font-medium text-gray-500">Application Deadline</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(job.application_deadline) }}</dd>
                                </div>
                                <div v-if="job.job_start_date">
                                    <dt class="font-medium text-gray-500">Expected Start Date</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(job.job_start_date) }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Employer's Other Jobs -->
                        <div v-if="employer_jobs.length > 0" class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Other Jobs by This Employer</h3>
                            <div class="space-y-4">
                                <div
                                    v-for="otherJob in employer_jobs"
                                    :key="otherJob.id"
                                    class="border border-gray-200 rounded-lg p-4"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900">{{ otherJob.title }}</h4>
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            otherJob.status === 'active' ? 'bg-green-100 text-green-800' :
                                            otherJob.status === 'filled' ? 'bg-blue-100 text-blue-800' :
                                            'bg-gray-100 text-gray-800'
                                        ]">
                                            {{ otherJob.status }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 space-y-1">
                                        <div>{{ otherJob.location }} • {{ formatSalary(otherJob) }}</div>
                                        <div>Posted {{ formatDate(otherJob.created_at) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Employer Information -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Employer Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Company Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.employer.company_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Verification Status</dt>
                                    <dd class="mt-1">
                                        <span :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            getVerificationStatusColor(job.employer.verification_status)
                                        ]">
                                            {{ job.employer.verification_status }}
                                        </span>
                                    </dd>
                                </div>
                                <div v-if="job.employer.industry">
                                    <dt class="text-sm font-medium text-gray-500">Industry</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.employer.industry }}</dd>
                                </div>
                                <div v-if="job.employer.company_size">
                                    <dt class="text-sm font-medium text-gray-500">Company Size</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ job.employer.company_size }}</dd>
                                </div>
                                <div v-if="job.employer.website">
                                    <dt class="text-sm font-medium text-gray-500">Website</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a :href="job.employer.website" target="_blank" class="text-indigo-600 hover:text-indigo-800">
                                            {{ job.employer.website }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Registered</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(job.employer.created_at) }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Approval Actions -->
                        <div class="bg-white shadow rounded-lg p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Review Actions</h3>
                            
                            <!-- Approve Form -->
                            <form @submit.prevent="approve" class="space-y-4 mb-6">
                                <div>
                                    <label for="approval_notes" class="block text-sm font-medium text-gray-700">
                                        Approval Notes (Optional)
                                    </label>
                                    <textarea
                                        id="approval_notes"
                                        v-model="approveForm.notes"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                                        placeholder="Add any notes for the employer..."
                                    ></textarea>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="approveForm.processing"
                                    class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                                >
                                    <span v-if="approveForm.processing">Approving...</span>
                                    <span v-else>Approve Job</span>
                                </button>
                            </form>

                            <!-- Reject Form -->
                            <form @submit.prevent="reject" class="space-y-4">
                                <div>
                                    <label for="rejection_reason" class="block text-sm font-medium text-gray-700">
                                        Rejection Reason *
                                    </label>
                                    <textarea
                                        id="rejection_reason"
                                        v-model="rejectForm.reason"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500"
                                        placeholder="Explain why this job is being rejected..."
                                        required
                                    ></textarea>
                                    <div v-if="rejectForm.errors.reason" class="text-red-600 text-sm mt-1">
                                        {{ rejectForm.errors.reason }}
                                    </div>
                                </div>
                                <button
                                    type="submit"
                                    :disabled="rejectForm.processing"
                                    class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium disabled:opacity-50"
                                >
                                    <span v-if="rejectForm.processing">Rejecting...</span>
                                    <span v-else>Reject Job</span>
                                </button>
                            </form>
                        </div>

                        <!-- Review Guidelines -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-medium text-blue-900 mb-4">Review Guidelines</h3>
                            <div class="space-y-3 text-sm text-blue-800">
                                <div class="flex items-start space-x-2">
                                    <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Check if job description is clear and professional</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Verify salary range is reasonable for the role</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Ensure requirements match the target course</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Review employer's verification status</span>
                                </div>
                                <div class="flex items-start space-x-2">
                                    <svg class="w-4 h-4 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Check for any discriminatory language</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>